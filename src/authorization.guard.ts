import { CanActivate, ExecutionContext, Injectable } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { GqlContextType, GqlExecutionContext } from '@nestjs/graphql';
import { Request } from 'express';
import { AuthorizationTokenService } from './authorization-token/authorization-token.service';
import { AUTH_METADATA_HAS_AUTHORIZATION, AUTH_METADATA_HAS_AUTHORIZATION_TYPE } from './authorization-token/constants';
import { HasTokenExpiredType } from './authorization-token/enums';
import { UNAUTHORIZED } from './constants';
import { Context } from './context';

@Injectable()
export class AuthorizationGuard implements CanActivate {
  constructor(
    private readonly context: Context,
    private readonly reflector: Reflector,
    private readonly authorizationTokenService: AuthorizationTokenService,
  ) {}

  resolveContext(context: ExecutionContext): Context {
    if (context.getType<GqlContextType>() === "graphql") {
      return GqlExecutionContext.create(context).getContext();
    }

    return this.context;
  }

  initializeContext(context: ExecutionContext) {
    if (context.getType() === 'http') {
      return this.context.create(context.switchToHttp().getRequest<Request>());
    }
  }

  getHasAuthorization(context: ExecutionContext) {
    return this.reflector.get<boolean>(
      AUTH_METADATA_HAS_AUTHORIZATION,
      context.getHandler(),
    );
  }

  getHasAuthorizationType(context: ExecutionContext) {
    return this.reflector.get<HasTokenExpiredType>(
      AUTH_METADATA_HAS_AUTHORIZATION_TYPE,
      context.getHandler(),
    );
  }

  canActivelyTokenHandler(
    context: ExecutionContext,
    type: HasTokenExpiredType,
  ): boolean {
    const { authorizationToken, user } = this.resolveContext(context);
    const has = !this.authorizationTokenService.hasTokenExpired(authorizationToken, type);

    return user && has;
  }

  async canActivate(
    context: ExecutionContext,
  ): Promise<boolean> {
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
