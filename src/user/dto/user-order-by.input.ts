import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserProfileOrderByInput } from '../profile/dto/user-profile-order-by.input';

/**
 * User order by input
 */
@InputType({ description: 'User order by input' })
export class UserOrderByInput implements Prisma.UserOrderByInput {
  /**
   * `id` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`id` field sort order',
  })
  id?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  username?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  phone?: Prisma.SortOrder;

  /**
   * Field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  password?: Prisma.SortOrder;

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
  @Field(() => UserProfileOrderByInput, {
    nullable: true,
    description: 'Field sort order',
  })
  profile?: UserProfileOrderByInput;
}
