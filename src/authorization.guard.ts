import { CanActivate, ExecutionContext, Injectable } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { GqlContextType, GqlExecutionContext } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { Request } from 'express';
import { AuthorizationTokenService } from './authorization-token/authorization-token.service';
import {
  AUTH_METADATA_HAS_AUTHORIZATION,
  AUTH_METADATA_HAS_AUTHORIZATION_TYPE,
} from './authorization-token/constants';
import { HasTokenExpiredType } from './authorization-token/enums';
import { UNAUTHORIZED } from './constants';
import { ExecutionContext as IContext } from './execution-context';

/**
 * Authorization guard.
 */
@Injectable()
export class AuthorizationGuard implements CanActivate {
  /**
   * Create Authorization guard.
   * @param reflector NestJS core reflector.
   * @param authorizationTokenService Authorization token service.
   * @param prismaClient Prisma Client.
   */
  constructor(
    private readonly reflector: Reflector,
    private readonly authorizationTokenService: AuthorizationTokenService,
    private readonly prismaClient: PrismaClient,
  ) {}

  /**
   * Resolver context.
   * @param context NestJS execution context.
   */
  resolveContext(context: ExecutionContext): IContext {
    if (context.getType<GqlContextType>() === 'graphql') {
      return GqlExecutionContext.create(context).getContext();
    }

    return context.switchToHttp().getRequest<Request>().context;
  }

  /**
   * Get token has need validate.
   * @param context NextJS execution context.
   */
  getHasAuthorization(context: ExecutionContext) {
    return this.reflector.get<boolean>(
      AUTH_METADATA_HAS_AUTHORIZATION,
      context.getHandler(),
    );
  }

  /**
   * Get token validate type.
   * @param context NestJS execution context
   */
  getHasAuthorizationType(context: ExecutionContext) {
    return this.reflector.get<HasTokenExpiredType>(
      AUTH_METADATA_HAS_AUTHORIZATION_TYPE,
      context.getHandler(),
    );
  }

  /**
   * Can activel token handler.
   * @param context NestJS execution context
   * @param type Token validate type.
   */
  canActivelyTokenHandler(
    context: ExecutionContext,
    type: HasTokenExpiredType,
  ): boolean {
    const { authorizationToken, user } = this.resolveContext(context);
    const has = !this.authorizationTokenService.hasTokenExpired(
      authorizationToken,
      type,
    );

    return user && has;
  }

  /**
   * Initialzd Socfony execution context.
   * @param context NestJS execution context
   */
  async initializeContext(context: ExecutionContext) {
    if (context.getType() === 'http') {
      return await IContext.create(
        this.prismaClient,
        context.switchToHttp().getRequest<Request>(),
      );
    }
  }

  /**
   * Can activate guard.
   * @param context NestJS execution context.
   */
  async canActivate(context: ExecutionContext): Promise<boolean> {
    await this.initializeContext(context);

    // If Don't validate or verified
    if (!this.getHasAuthorization(context)) return true;

    // get `Authorization` token validate type
    const type = this.getHasAuthorizationType(context);

    if (
      !this.canActivelyTokenHandler(context, type || HasTokenExpiredType.AUTH)
    ) {
      throw new Error(UNAUTHORIZED);
    }

    return true;
  }
}
