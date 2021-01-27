import { Request } from 'express';
import { AuthorizationToken, Prisma, PrismaClient, User } from '@prisma/client';

export class ExecutionContext {
  private constructor(
    public request: Request,
    public authorizationToken?: AuthorizationToken,
    public user?: User,
  ) {}

  /**
   * Create kernel context.
   * @param request Express request.
   */
  static async create(prismaClient: PrismaClient, request: Request) {
    const token = this.getHttpAuthorization(request);
    if (
      request.context &&
      request.context.authorizationToken?.token === token
    ) {
      return this;
    }

    const authorizationToken = await this.getAuthorizationToken(
      prismaClient,
      token,
    );
    const context = new ExecutionContext(
      request,
      authorizationToken,
      authorizationToken?.user,
    );
    context.request.context = request.context = context;

    return context;
  }

  /**
   * Get HTTP endpoint `Authorization` header value.
   * @param request Express request.
   */
  private static getHttpAuthorization(request: Request): string {
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
  private static getAuthorizationToken(
    prismaClient: PrismaClient,
    token: string,
  ): Promise<Prisma.AuthorizationTokenGetPayload<{ include: { user: true } }>> {
    if (token)
      return prismaClient.authorizationToken.findUnique({
        where: { token: token },
        include: { user: true },
        rejectOnNotFound: false,
      });
  }
}
