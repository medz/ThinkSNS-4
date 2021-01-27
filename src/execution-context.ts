import { Request } from 'express';
import { AuthorizationToken, Prisma, PrismaClient, User } from '@prisma/client';

export class ExecutionContext {
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

    const token = this.getHttpAuthorization(request);
    if (this.authorizationToken?.token === token) {
      return this;
    }

    const authorizationToken = await this.getAuthorizationToken(token);
    this.authorizationToken = authorizationToken;
    this.user = authorizationToken?.user;

    return this;
  }

  /**
   * Get HTTP endpoint `Authorization` header value.
   * @param request Express request.
   */
  private getHttpAuthorization(request: Request): string {
    const key = 'Authorization';
    if (request.header instanceof Function) {
      return request.header(key);
    }

    const headers = request.headers;
    let token = headers[key.toLowerCase()];
    if (!token || !token.length) {
      token = headers[key];
    }

    return Array.isArray(token) ? token.pop() : token;
  }

  /**
   * Get `AuthorizationToken`
   * @param token Token string.
   */
  private getAuthorizationToken(
    token: string,
  ): Promise<Prisma.AuthorizationTokenGetPayload<{ include: { user: true } }>> {
    if (token)
      return this.prisma.authorizationToken.findUnique({
        where: { token: token },
        include: { user: true },
        rejectOnNotFound: false,
      });
  }
}
