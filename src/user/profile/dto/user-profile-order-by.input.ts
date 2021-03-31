import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';

/**
 * User profile order by input
 */
@InputType({ description: 'User profile order by input' })
export class UserProfileOrderByInput implements Prisma.UserProfileOrderByInput {
  /**
   * `userId` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`userId` field sort order',
  })
  userId?: Prisma.SortOrder;

  /**
   * `name` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`name` field sort order',
  })
  name?: Prisma.SortOrder;

  /**
   * `avatar` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`avatar` field sort order',
  })
  avatar?: Prisma.SortOrder;

  /**
   * `bio` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`bio` field sort order',
  })
  bio?: Prisma.SortOrder;

  /**
   * `location` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`location` field sort order',
  })
  location?: Prisma.SortOrder;

  /**
   * `updatedAt` field sort order
   */
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: '`updatedAt` field sort order',
  })
  updatedAt?: Prisma.SortOrder;
}
