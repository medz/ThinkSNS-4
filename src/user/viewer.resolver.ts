import {
  Args,
  Mutation,
  Parent,
  Query,
  ResolveField,
  Resolver,
} from '@nestjs/graphql';
import { PrismaClient, User } from '@prisma/client';
import { AuthorizationWith } from 'src/authorization.decorator';
import {
  SECURITY_COMPARE_FAILED,
  USER_EMAIL_FIELD_EXISTED,
  USER_LOGIN_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
} from 'src/constants';
import { Context } from 'src/context.decorator';
import { ExecutionContext } from 'src/execution-context';
import { PasswordHelper } from 'src/helper';
import { UpdateViewerArgs } from './dto/update-viewer.args';
import { ViewerEntity } from './entities/viewer.entity';
import { UserSecurityCompareType } from './enums';
import { UserService } from './user.service';

/**
 * User error codes.
 */
const constants = {
  USER_LOGIN_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
  USER_EMAIL_FIELD_EXISTED,
};

/**
 * Viewer entity resolver.
 */
@Resolver(() => ViewerEntity)
export class ViewerResolver {
  /**
   * Create viewer entity resolver.
   * @param userService User service.
   * @param prismaClient Prisma client.
   */
  constructor(
    private readonly userService: UserService,
    private readonly prismaClient: PrismaClient,
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
   * Query the HTTP endpoint Authorization user.
   * @param context Socfony execution context.
   */
  @Query(() => ViewerEntity, {
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
  @Mutation(() => ViewerEntity, {
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
}
