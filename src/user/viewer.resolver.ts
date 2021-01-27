import { Args, Mutation, Resolver } from '@nestjs/graphql';
import { PrismaClient } from '@prisma/client';
import { HasTokenExpiredType } from 'src/authorization-token/enums';
import { Authorization } from 'src/authorization.decorator';
import {
  SECURITY_COMPARE_FAILED,
  USER_EMAIL_FIELD_EXISTED,
  USER_LOGIN_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
} from 'src/constants';
import { Context } from 'src/context';
import { PasswordHelper } from 'src/helper';
import { UpdateViewerArgs } from './dto/update-viewer.args';
import { ViewerEntity } from './entities/viewer.entity';
import { UserSecurityCompareType } from './enums';
import { UserService } from './user.service';

const constants = {
  USER_LOGIN_FIELD_EXISTED,
  USER_PHONE_FIELD_EXISTED,
  USER_EMAIL_FIELD_EXISTED,
};

@Resolver(() => ViewerEntity)
export class ViewerResolver {
  constructor(
    private readonly context: Context,
    private readonly userService: UserService,
    private readonly prismaClient: PrismaClient,
  ) {}

  @Mutation(() => ViewerEntity)
  @Authorization({ hasAuthorization: true, type: HasTokenExpiredType.AUTH })
  async updateViewer(
    @Args({
      type: () => UpdateViewerArgs,
      description: 'Update viewer args.',
    })
    args: UpdateViewerArgs,
  ) {
    const { data, security, type, newPhoneSecurity } = args;
    const { user } = this.context;
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
      return (this.context.user = await this.prismaClient.user.update({
        where: { id: user.id },
        data,
      }));
    }

    throw new Error(SECURITY_COMPARE_FAILED);
  }
}
