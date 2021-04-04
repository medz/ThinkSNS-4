import { Args, Mutation, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { AuthorizationWith } from 'src/authorization.decorator';
import {
  SECURITY_COMPARE_FAILED,
  USER_PHONE_FIELD_EXISTED,
  USER_UPDATE_PHONE_SECURITY_COMPARE_FAILED,
  USER_USERNAME_FIELD_EXISTED,
} from 'src/constants';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { PasswordHelper } from 'src/helper';
import { UserUpdatePasswordArgs } from '../dto/user-update-password.args';
import { UserUpdatePhoneArgs } from '../dto/user-update-phone.args';
import { UserUpdateUsernameArgs } from '../dto/user-update-username.args';
import { UserEntity } from '../entities/user.entity';
import { UserSecurityCompareType } from '../enums';
import { UserService } from '../user.service';

/**
 * User mutation resolver.
 */
@Resolver(() => UserEntity)
export class UserMutationResolver {
  /**
   * User mutation resolver constructor.
   * @param userService User service.
   * @param prismaClient Prisma client.
   */
  constructor(
    private readonly prismaClient: PrismaClient,
    private readonly userService: UserService,
  ) {}

  /**
   * Update user password.
   * @param context App context
   * @param args User update password args
   * @returns UserEntity
   */
  @Mutation(() => UserEntity, {
    description: 'Update viewer password',
  })
  @AuthorizationWith()
  async updateViewerPassword(
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
   * Update viewer username
   * @param context App context
   * @param args User update username args
   * @returns UserEntity
   */
  @Mutation(() => UserEntity, {
    description: 'Update viewer username field',
  })
  @AuthorizationWith()
  async updateViewerUsername(
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
    description: 'Update viewer phone field',
  })
  @AuthorizationWith()
  async updateViewerPhone(
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
