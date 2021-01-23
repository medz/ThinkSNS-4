import { applyDecorators, SetMetadata, UseGuards } from '@nestjs/common';
import { AuthGuard } from './auth.guard';
import {
  AUTH_METADATA_HAS_AUTHORIZATION,
  AUTH_METADATA_HAS_AUTHORIZATION_TYPE,
} from './constants';
import { HasTokenExpiredType } from './enums';

export interface AuthDecoratorOptions {
  hasAuthorization?: boolean;
  type?: HasTokenExpiredType;
}

/**
 * Need validate HTTP endpoint `Authorization` token decorator.
 * @param param validate HTTP endpoit `Authorization` token options.
 * @param param.hasAuthorization Has need validate
 * @param param.type validate token type.
 */
export function Authorization(options: AuthDecoratorOptions) {
  return applyDecorators(
    SetMetadata(AUTH_METADATA_HAS_AUTHORIZATION, options?.hasAuthorization),
    SetMetadata(
      AUTH_METADATA_HAS_AUTHORIZATION_TYPE,
      options?.type || HasTokenExpiredType.AUTH,
    ),
    UseGuards(AuthGuard),
  );
}
