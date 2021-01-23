import { Request } from 'express';
import { AuthorizationToken, PrismaClient, User } from '@socfony/prisma';
import { Injectable } from '@nestjs/common';

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
    this.authorizationToken = await this.getAuthorizationToken(
      this.getHttpAuthorization(request),
    );
    this.user = await this.getAuthorizationUser(this.authorizationToken);

    // @ts-ignore
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
  private getAuthorizationToken(token: string): Promise<AuthorizationToken> {
    if (token)
      return this.prisma.authorizationToken.findUnique({
        where: { token: token },
      });
  }

  /**
   * Get AuthorizationToken bound user.
   * @param authorizationToken `AuthorizationToken`
   */
  private getAuthorizationUser(
    authorizationToken: AuthorizationToken,
  ): Promise<User> {
    if (authorizationToken && authorizationToken.userId)
      return this.prisma.user.findUnique({
        where: { id: authorizationToken.userId },
      });
  }
}
