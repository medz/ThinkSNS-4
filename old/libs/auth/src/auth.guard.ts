import { Reflector } from '@nestjs/core';
import { ExecutionContext, Injectable } from '@nestjs/common';
import { Context } from '@socfony/kernel';
import { AuthService } from './auth.service';
import {
  AUTH_METADATA_HAS_AUTHORIZATION,
  AUTH_METADATA_HAS_AUTHORIZATION_TYPE,
} from './constants';
import { HasTokenExpiredType } from './enums';
import { Request } from 'express';
import { UNAUTHORIZED } from '@socfony/error-code';

@Injectable()
export class AuthGuard {
  constructor(
    protected readonly reflector: Reflector,
    protected readonly context: Context,
    protected readonly authService: AuthService,
  ) {}

  resolveContext(_context: ExecutionContext): Context {
    return this.context;
  }

  initializeContext(context: ExecutionContext) {
    if (context.getType() === 'http') {
      this.context.create(context.switchToHttp().getRequest<Request>());
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

  /**
   * Can activate for HTTP endpoint `Authorization` token.
   * @param context NestJS context
   */
  async canActivate(context: ExecutionContext): Promise<boolean> {
    this.initializeContext(context);

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

  canActivelyTokenHandler(
    context: ExecutionContext,
    type: HasTokenExpiredType,
  ): boolean {
    const { authorizationToken, user } = this.resolveContext(context);
    const has = !this.authService.hasTokenExpired(authorizationToken, type);

    return user && has;
  }
}
