import { applyDecorators, SetMetadata, UseGuards } from "@nestjs/common";
import { AUTH_METADATA_HAS_AUTHORIZATION, AUTH_METADATA_HAS_AUTHORIZATION_TYPE } from "./authorization-token/constants";
import { HasTokenExpiredType } from "./authorization-token/enums";
import { AuthorizationGuard } from "./authorization.guard";


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
      UseGuards(AuthorizationGuard),
    );
}
