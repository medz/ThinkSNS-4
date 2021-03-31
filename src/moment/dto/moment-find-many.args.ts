import { ArgsType, Field, Int, registerEnumType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { MomentOrderByInput } from './moment-order-by.input';
import { MomentWhereUniqueInput } from './moment-where-unique.input';
import { MomentWhereInput } from './moment-where.input';

/**
 * Register moment scalar field enum.
 */
registerEnumType(Prisma.MomentScalarFieldEnum, {
  name: 'MomentScalarFieldEnum',
  description: 'Moment scalar field enum.',
});

/**
 * Moment find many args.
 */
@ArgsType()
export class MomentFindManyArgs
  implements
    Pick<
      Prisma.MomentFindManyArgs,
      'where' | 'orderBy' | 'cursor' | 'take' | 'skip' | 'distinct'
    > {
  /**
   * Filter, which Moments to fetch.
   */
  @Field(() => MomentWhereInput, {
    nullable: true,
    description: 'Filter, which Moments to fetch.',
  })
  where?: MomentWhereInput;

  /**
   * Determine the order of Moments to fetch.
   */
  @Field(() => [MomentOrderByInput], {
    nullable: true,
    description: 'Determine the order of Moments to fetch.',
  })
  orderBy?: MomentOrderByInput[];

  /**
   * Sets the position for listing Moments.
   **/
  @Field(() => MomentWhereUniqueInput, {
    nullable: true,
    description: 'Sets the position for listing Moments',
  })
  cursor?: MomentWhereUniqueInput;

  /**
   * Take `±n` Moments from the position of the cursor.
   **/
  @Field(() => Int, {
    nullable: true,
    description: 'Take `±n` Moments from the position of the cursor.',
    defaultValue: 15,
  })
  take?: number;

  /**
   * Skip the first `n` Moments.
   **/
  @Field(() => Int, {
    nullable: true,
    description: 'Skip the first `n` Moments.',
    defaultValue: 0,
  })
  skip?: number;

  /**
   * Sets the query distinct
   */
  @Field(() => [Prisma.MomentScalarFieldEnum], {
    nullable: true,
    description: 'Sets the query distinct',
  })
  distinct?: Prisma.MomentScalarFieldEnum[];
}
