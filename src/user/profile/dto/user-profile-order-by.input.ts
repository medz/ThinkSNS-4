import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';

@InputType({ description: 'User profile order by input' })
export class UserProfileOrderByInput implements Prisma.UserProfileOrderByInput {
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  userId?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  name?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  avatar?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  bio?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  location?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  updatedAt?: Prisma.SortOrder;
}
