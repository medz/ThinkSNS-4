import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { MomentWhereInput } from './moment-where.input';

/**
 * Moment list relation filter
 */
@InputType({ description: 'Moment list relation filter' })
export class MomentListRelationFilter
  implements Prisma.MomentListRelationFilter {
  /**
   * Moment list relation filter every where input.
   */
  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Moment list relation filter every where input.',
  })
  every?: MomentWhereInput;

  /**
   * Moment list relation filter some where input.
   */
  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Moment list relation filter some where input.',
  })
  some?: MomentWhereInput;

  /**
   * Moment list relation filter none where input.
   */
  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Moment list relation filter none where input.',
  })
  none?: MomentWhereInput;
}
