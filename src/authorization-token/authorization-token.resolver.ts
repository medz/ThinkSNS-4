import {
  Args,
  Field,
  Mutation,
  Parent,
  Query,
  Resolver,
} from '@nestjs/graphql';
import { Prisma, PrismaClient } from '@prisma/client';
import { Authorization } from 'src/authorization.decorator';
import {
  AUTHORIZATION_TOKEN_CREATE_USER_WHERE_ALLOW_ONE,
  USER_NOT_FOUND,
} from 'src/constants';
import { Context } from 'src/context';
import { IDHelper } from 'src/helper';
import { ViewerEntity } from 'src/user/entities/viewer.entity';
import { UserSecurityCompareType } from 'src/user/enums';
import { AuthorizationTokenService } from './authorization-token.service';
import { CreateAuthorizationTokenArgs } from './dto/create-authorization-token.args';
import { AuthorizationTokenEntity } from './entities/authorization-token.entity';
import { HasTokenExpiredType } from './enums';

@Resolver(() => AuthorizationTokenEntity)
export class AuthorizationTokenEntityResolver {
  constructor(
    private readonly authorizationTokenService: AuthorizationTokenService,
    private readonly prisma: PrismaClient,
    private readonly context: Context,
  ) {}

  @Field(() => ViewerEntity)
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

  @Query(() => AuthorizationTokenEntity)
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.AUTH,
  })
  authorizationToken() {
    return this.context.authorizationToken;
  }

  @Mutation(() => AuthorizationTokenEntity)
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
    if (type === UserSecurityCompareType.SMS_CODE && !user && where.phone) {
      user = await this.prisma.user.create({
        data: {
          id: IDHelper.id(32),
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
  @Mutation((returns) => AuthorizationTokenEntity, {
    description: 'Refresh HTTP endpoint authorization token entity.',
  })
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.REFRESH,
  })
  refreshAuthorizationToken() {
    return this.authorizationTokenService.refreshAuthorizationToken(
      this.context.authorizationToken,
    );
  }
}
