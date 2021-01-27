import { Request } from 'express';
import { Injectable } from '@nestjs/common';
import { AuthorizationToken, Prisma, PrismaClient, User } from '@prisma/client';

@Injectable()
export class Context {
  constructor(private readonly prisma: PrismaClient) {}

  /**
   * Authenticated user token
   */
  authorizationToken: AuthorizationToken;

  /**
   * Authenticated user
   */
  user: User;

  /**
   * Express request
   */
  request?: Request;

  /**
   * Create kernel context.
   * @param request Express request.
   */
  async create(request: Request) {
    this.request = request;
    const token = await this.getAuthorizationToken(
      this.getHttpAuthorization(request),
    );
    this.authorizationToken = token;
    this.user = token?.user;

    return (this.request.context = this);
  }

  /**
   * Get HTTP endpoint `Authorization` header value.
   * @param request Express request.
   */
  private getHttpAuthorization(request: Request): string {
    if (request) return request.header('Authorization');
  }

  /**
   * Get `AuthorizationToken`
   * @param token Token string.
   */
  private getAuthorizationToken(token: string): Promise<Prisma.AuthorizationTokenGetPayload<{ include: { user: true; } }>> {
    if (token)
      return this.prisma.authorizationToken.findUnique({
        where: { token: token },
        include: { user: true, },
        rejectOnNotFound: false,
      });
  }
}
