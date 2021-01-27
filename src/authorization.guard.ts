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

@Injectable()
export class AuthorizationGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly authorizationTokenService: AuthorizationTokenService,
    private readonly prismaClient: PrismaClient,
  ) {}

  resolveContext(context: ExecutionContext): IContext {
    if (context.getType<GqlContextType>() === 'graphql') {
      return GqlExecutionContext.create(context).getContext();
    }

    return context.switchToHttp().getRequest<Request>().context;
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
    const has = !this.authorizationTokenService.hasTokenExpired(
      authorizationToken,
      type,
    );

    return user && has;
  }

  async initializeContext(context: ExecutionContext) {
    if (context.getType() === 'http') {
      return await IContext.create(
        this.prismaClient,
        context.switchToHttp().getRequest<Request>(),
      );
    }
  }

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
