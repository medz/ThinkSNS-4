import {
  Args,
  Mutation,
  Parent,
  Query,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import { PrismaClient, User } from '@prisma/client';
import { UserEntity } from './entities/user.entity';
import {
  SECURITY_COMPARE_FAILED,
  USER_USERNAME_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
  USER_NOT_FOUND,
  USER_UPDATE_PHONE_SECURITY_COMPARE_FAILED,
} from 'src/constants';
import { UserService } from './user.service';
import { AuthorizationWith } from 'src/authorization.decorator';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { PasswordHelper } from 'src/helper';
import { UserSecurityCompareType } from './enums';
import { UserProfileEntity } from './profile/entities/profile.entity';
import { UserProfileService } from './profile/profile.service';
import { UserUpdatePasswordArgs } from './dto/user-update-password.args';
import { UserUpdateUsernameArgs } from './dto/user-update-username.args';
import { UserUpdatePhoneArgs } from './dto/user-update-phone.args';
import { UserWhereUniqueInput } from './dto/user-where-unique.input';

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
   * Query a user where unique
   * @param where Query a user where unique
   */
  @Query(() => UserEntity, {
    description: 'Find zero or one User that matches the filter.',
  })
  userFindUnique(
    @Args({
      name: 'where',
      type: () => UserWhereUniqueInput,
      description: 'User where unique input',
    })
    where: UserWhereUniqueInput,
  ) {
    return this.prismaClient.user.findUnique({
      where,
      rejectOnNotFound: () => new Error(USER_NOT_FOUND),
    });
  }

  /**
   * Update user password.
   * @param context App context
   * @param args User update password args
   * @returns UserEntity
   */
  @Mutation(() => UserEntity, {
    description: 'Update User password',
  })
  @AuthorizationWith()
  async updateUserPassword(
    @Context() context: ExecutionContext,
    @Args({
      type: () => UserUpdatePasswordArgs,
      description: 'User update password args',
    })
    args: UserUpdatePasswordArgs,
  ) {
    const { password, type, security } = args;
    const compared = await this.userService.compareSecurity(
      context.user,
      type,
      security,
    );
    if (!compared || !(compared instanceof Function)) {
      throw new Error(SECURITY_COMPARE_FAILED);
    }

    compared();

    return await this.prismaClient.user.update({
      where: { id: context.user.id },
      data: { password: await PasswordHelper.hash(password) },
    });
  }

  /**
   * 用户更新用户名
   * @param context 当前请求的上下文
   * @param args 操作的参数
   * @returns UserEntity
   */
  @Mutation(() => UserEntity, {
    description: 'Update user username field',
  })
  @AuthorizationWith()
  async updateUserUsername(
    @Context() context: ExecutionContext,
    @Args({
      type: () => UserUpdateUsernameArgs,
      description: 'User update username args',
    })
    args: UserUpdateUsernameArgs,
  ) {
    // 获取参数
    const { username, type, security } = args;

    // 检查是否是更新用户名，如果没有用户名存在则直接返回当前用户信息
    if (!username) {
      return context.user;
    }

    // 检查用户名是否被其他用户占用
    const other = await this.prismaClient.user.findUnique({
      where: { username },
      rejectOnNotFound: false,
    });
    if (other && other.id !== context.user.id) {
      throw new Error(USER_USERNAME_FIELD_EXISTED);
    }

    /// 验证安全验证是否成功，失败则返回安全验证失败代码
    const compared = await this.userService.compareSecurity(
      context.user,
      type,
      security,
    );
    if (!compared || !(compared instanceof Function)) {
      throw new Error(SECURITY_COMPARE_FAILED);
    }

    compared();

    return await this.prismaClient.user.update({
      where: { id: context.user.id },
      data: { username },
    });
  }

  /**
   * User update phone.
   * @param context App Context
   * @param args User update phone args.
   * @returns UserEntity
   */
  @Mutation(() => UserEntity, {
    description: 'Update user phone field',
  })
  @AuthorizationWith()
  async updateUserPhone(
    @Context() context: ExecutionContext,
    @Args({
      type: () => UserUpdatePhoneArgs,
      description: 'User update phone args',
    })
    args: UserUpdatePhoneArgs,
  ) {
    const { type, security, phone, newPhoneSecurity } = args;
    if (!phone) {
      return context.user;
    }

    const other = await this.prismaClient.user.findUnique({
      where: { phone },
    });
    if (other && other.id !== context.user.id) {
      throw new Error(USER_PHONE_FIELD_EXISTED);
    }

    /// 验证安全验证是否成功，失败则返回安全验证失败代码
    const compared = await this.userService.compareSecurity(
      context.user,
      type,
      security,
    );
    if (!compared || !(compared instanceof Function)) {
      throw new Error(SECURITY_COMPARE_FAILED);
    }

    const newPhoneCompared = await this.userService.compareSecurity(
      Object.assign({}, context.user, { phone }),
      UserSecurityCompareType.PHONE_SMS_CODE,
      newPhoneSecurity,
    );
    if (!newPhoneCompared || !(newPhoneCompared instanceof Function)) {
      throw new Error(USER_UPDATE_PHONE_SECURITY_COMPARE_FAILED);
    }

    compared();
    newPhoneCompared();

    return await this.prismaClient.user.update({
      where: { id: context.user.id },
      data: { phone },
    });
  }
}
