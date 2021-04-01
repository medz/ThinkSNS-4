import { Field, InputType, PartialType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserEntity } from '../entities/user.entity';

/**
 * User update input.
 */
@InputType({
  description: 'User update input',
})
export class UserUpdateInput
  extends PartialType(PickType(UserEntity, ['username', 'phone']), InputType)
  implements Pick<Prisma.UserUpdateInput, 'username' | 'phone' | 'password'> {
  /**
   * User new password.
   */
  @Field(() => String, {
    description: 'User new password',
    nullable: true,
  })
  password?: string;
}
