import { ArgsType, Field, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { CreateAuthorizationTokenArgs } from 'src/authorization-token/dto/create-authorization-token.args';

/**
 * User update password args.
 */
@ArgsType()
export class UserUpdatePasswordArgs
  extends PickType(
    CreateAuthorizationTokenArgs,
    ['security', 'type'] as const,
    ArgsType,
  )
  implements Pick<Prisma.UserUpdateInput, 'password'> {
  /**
   * User new password.
   */
  @Field(() => String, {
    description: 'User new password',
    nullable: false,
  })
  password?: string;
}
