import { Field, ID, InputType, PartialType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { ViewerEntity } from '../entities/viewer.entity';

/**
 * User where inique input
 */
@InputType({
  description: 'User where inique input',
})
export class UserWhereUniqueInput
  extends PickType(
    PartialType(ViewerEntity),
    ['id', 'login', 'email', 'phone'] as const,
    InputType,
  )
  implements Prisma.UserWhereUniqueInput {}
