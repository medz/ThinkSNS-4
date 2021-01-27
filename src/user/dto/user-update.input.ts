import { Field, InputType, PartialType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { ViewerEntity } from '../entities/viewer.entity';

@InputType()
export class UserUpdateInput
  extends PartialType(
    PickType(ViewerEntity, ['email', 'login', 'phone']),
    InputType,
  )
  implements
    Pick<Prisma.UserUpdateInput, 'login' | 'phone' | 'email' | 'password'> {
  @Field(() => String, {
    description: 'User new password',
    nullable: true,
  })
  password?: string;
}
