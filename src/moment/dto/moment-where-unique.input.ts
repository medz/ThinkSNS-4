import { InputType, PartialType, PickType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { MomentEntity } from '../entities/moment.entity';

@InputType({ description: 'Moment Where Unique input' })
export class MomentWhereUniqueInput
  extends PartialType(PickType(MomentEntity, ['id'] as const), InputType)
  implements Prisma.MomentWhereUniqueInput {}
