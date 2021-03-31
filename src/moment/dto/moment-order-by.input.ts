import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserOrderByInput } from 'src/user/dto/user-order-by.input';

/**
 * Moment order by input
 */
@InputType({ description: 'Moment order by input' })
export class MomentOrderByInput implements Prisma.MomentOrderByInput {
  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  id?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  title?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  content?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  media?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  createdAt?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  deletedAt?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  ownerId?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => UserOrderByInput, {
    nullable: true,
    description: 'Field sort order',
  })
  owner?: UserOrderByInput;
}
