import {
  Args,
  Mutation,
  Parent,
  Query,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import { Prisma, PrismaClient } from '@prisma/client';
import { Authorization } from 'src/authorization.decorator';
import {
  AUTHORIZATION_TOKEN_CREATE_USER_WHERE_ALLOW_ONE,
  USER_NOT_FOUND,
} from 'src/constants';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { IDHelper } from 'src/helper';
import { UserEntity } from 'src/user/entities/user.entity';
import { UserSecurityCompareType } from 'src/user/enums';
import { AuthorizationTokenService } from './authorization-token.service';
import { CreateAuthorizationTokenArgs } from './dto/create-authorization-token.args';
import { AuthorizationTokenEntity } from './entities/authorization-token.entity';
import { HasTokenExpiredType } from './enums';

/**
 * AuthorizationTokenEntity resolver.
 */
@Resolver(() => AuthorizationTokenEntity)
export class AuthorizationTokenEntityResolver {
  /**
   * Create Authorization resolver.
   * @param authorizationTokenService Authorization token service.
   * @param prisma Prisma client.
   */
  constructor(
    private readonly authorizationTokenService: AuthorizationTokenService,
    private readonly prisma: PrismaClient,
  ) {}

  /**
   * Resolve AuthorizationTokenEntity user field.
   * @param token HTTP endpoint AuthorizationToken
   */
  @ResolveField(() => UserEntity)
  user(
    @Parent()
    token: Prisma.AuthorizationTokenGetPayload<{
      include: { user: true };
    }>,
  ) {
    const { user } = token;
    if (user) {
      return user;
    }

    return this.prisma.user.findUnique({
      where: { id: token.userId },
    });
  }

  /**
   * Query HTTP endpoint AuthorizationTokenEntity
   * @param context App runner execution context.
   */
  @Query(() => AuthorizationTokenEntity, {
    description: 'Query HTTP endpoint AuthorizationTokenEntity',
  })
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.AUTH,
  })
  authorizationToken(@Context() context: ExecutionContext) {
    return context.authorizationToken;
  }

  /**
   * Create AuthorizationTokenEntity
   * @param args Create `AuthorizationTokenEntity` args.
   */
  @Mutation(() => AuthorizationTokenEntity, {
    description: 'Create AuthorizationTokenEntity',
  })
  async createAuthorizationToken(
    @Args({
      type: () => CreateAuthorizationTokenArgs,
    })
    args: CreateAuthorizationTokenArgs,
  ) {
    const { type, user: where, security } = args;
    if (Object.keys(where).length > 1) {
      throw new Error(AUTHORIZATION_TOKEN_CREATE_USER_WHERE_ALLOW_ONE);
    }

    let user = await this.prisma.user.findUnique({
      where,
      rejectOnNotFound: false,
    });
    if (
      type === UserSecurityCompareType.PHONE_SMS_CODE &&
      !user &&
      where.phone
    ) {
      user = await this.prisma.user.create({
        data: {
          id: IDHelper.id(64),
          phone: where.phone,
        },
      });
    } else if (!user) {
      throw new Error(USER_NOT_FOUND);
    }

    return this.authorizationTokenService.createTokenWithSecurity(
      user,
      type,
      security,
    );
  }

  /**
   * Refresh HTTP endpoint authorization token entity.
   * @param client token query Prisma client.
   */
  @Mutation(() => AuthorizationTokenEntity, {
    description: 'Refresh HTTP endpoint authorization token entity.',
  })
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.REFRESH,
  })
  refreshAuthorizationToken(@Context() context: ExecutionContext) {
    return this.authorizationTokenService.refreshAuthorizationToken(
      context.authorizationToken,
    );
  }
}
