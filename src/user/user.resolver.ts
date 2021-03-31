import {
  Args,
  Mutation,
  Parent,
  Query,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import { PrismaClient, User } from '@prisma/client';
import { USER_NOT_FOUND } from 'src/constants';
import { UserWhereUniqueInput } from './dto';
import { UserEntity } from './entities/user.entity';
import {
  SECURITY_COMPARE_FAILED,
  USER_EMAIL_FIELD_EXISTED,
  USER_LOGIN_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
} from 'src/constants';
import { UserService } from './user.service';
import { AuthorizationWith } from 'src/authorization.decorator';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { UpdateViewerArgs } from './dto/update-viewer.args';
import { PasswordHelper } from 'src/helper';
import { UserSecurityCompareType } from './enums';
import { UserProfileEntity } from './profile/entities/profile.entity';
import { UserProfileService } from './profile/profile.service';

/**
 * User error codes.
 */
const constants = {
  USER_LOGIN_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
  USER_EMAIL_FIELD_EXISTED,
};

/**
 * User entity resolver
 */
@Resolver(() => UserEntity)
export class UserResolver {
  /**
   * Create user entity resolver.
   * @param userService User service.
   * @param prismaClient Prisma client.
   */
  constructor(
    private readonly prismaClient: PrismaClient,
    private readonly userService: UserService,
    private readonly userProfileService: UserProfileService,
  ) {}

  /**
   * Resolver viewer phone field.
   * @param user Parent context user.
   */
  @ResolveField(() => String)
  phone(@Parent() user: User) {
    const { phone } = user;
    if (phone) {
      return phone.replace(/(.*)\d{4}(\d{4})/, '$1****$2');
    }

    return phone;
  }

  /**
   * Resolve viewer email field.
   * @param user Parent context user.
   */
  @ResolveField(() => String)
  email(@Parent() user: User) {
    const { email } = user;
    if (email) {
      return email.replace(/^(\w{1}).*?(\w{1}?)\@(.*)$/, '$1***$2@$3');
    }

    return email;
  }

  /**
   * resolve user `isSetPassword` field.
   * @param user Parent context user.
   * @returns boolean
   */
  @ResolveField(() => Boolean)
  isSetPassword(@Parent() user: User) {
    return !!user.password;
  }

  /**
   * Resolve user profile field.
   * @param user Parent context user.
   */
  @ResolveField(() => UserProfileEntity)
  profile(@Parent() user: User) {
    return this.userProfileService.resolveProfile(user);
  }

  /**
   * Query the HTTP endpoint Authorization user.
   * @param context Socfony execution context.
   */
  @Query(() => UserEntity, {
    description: 'Query the HTTP endpoint Authorization user.',
  })
  @AuthorizationWith()
  viewer(@Context() context: ExecutionContext) {
    return context.user;
  }

  /**
   * Update viewer.
   * @param context Scofony exection context
   * @param args Update viewer args.
   */
  @Mutation(() => UserEntity, {
    description: 'Update viewer',
  })
  @AuthorizationWith()
  async updateViewer(
    @Context() context: ExecutionContext,
    @Args({
      type: () => UpdateViewerArgs,
      description: 'Update viewer args.',
    })
    args: UpdateViewerArgs,
  ) {
    const { data, security, type, newPhoneSecurity } = args;
    const { user } = context;

    for await (const key of Object.keys(data)) {
      // Convert to password hash
      if (key === 'password') {
        data[key] = await PasswordHelper.hash(data[key]);
        continue;
      }

      // Check whether the unique field is used by other users
      const other = await this.prismaClient.user.findUnique({
        where: { [key]: data[key] },
        rejectOnNotFound: false,
      });
      if (other && other.id !== user.id) {
        throw new Error(constants[`USER_${key.toUpperCase()}_FIELD_EXISTED`]);
      }

      // Check whether the new mobile phone number verification code matches
      if (key === 'phone' && data[key] !== user.phone) {
        const compared = await this.userService.compareSecurity(
          Object.assign({}, user, { phone: data[key] }),
          UserSecurityCompareType.SMS_CODE,
          newPhoneSecurity,
        );
        if (compared) {
          if (compared instanceof Function) {
            compared();
          }
          continue;
        }

        throw new Error(SECURITY_COMPARE_FAILED);
      }
    }

    // compared user security.
    const compared = await this.userService.compareSecurity(
      user,
      type,
      security,
    );
    if (compared) {
      if (compared instanceof Function) {
        compared();
      }
      return (context.user = await this.prismaClient.user.update({
        where: { id: user.id },
        data,
      }));
    }

    throw new Error(SECURITY_COMPARE_FAILED);
  }

  /**
   * Query a user where unique
   * @param where Query a user where unique
   */
  @Query(() => UserEntity, {
    description: 'query where.',
  })
  user(
    @Args({
      name: 'where',
      type: () => UserWhereUniqueInput,
      description: 'query where',
    })
    where: UserWhereUniqueInput,
  ) {
    return this.prismaClient.user.findUnique({
      where,
      rejectOnNotFound: () => new Error(USER_NOT_FOUND),
    });
  }
}
