import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { MomentWhereInput } from './moment-where.input';

@InputType({ description: 'Moment list relation filter' })
export class MomentListRelationFilter
  implements Prisma.MomentListRelationFilter {
  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Moment list relation filter every where input.',
  })
  every?: MomentWhereInput;

  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Moment list relation filter some where input.',
  })
  some?: MomentWhereInput;

  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Moment list relation filter none where input.',
  })
  none?: MomentWhereInput;
}
