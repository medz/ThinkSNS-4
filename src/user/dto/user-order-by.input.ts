import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserProfileOrderByInput } from '../profile/dto/user-profile-order-by.input';

@InputType({ description: 'User order by input' })
export class UserOrderByInput implements Prisma.UserOrderByInput {
  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  id?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  login?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  email?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  phone?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  password?: Prisma.SortOrder;

  @Field(() => Prisma.SortOrder, {
    nullable: true,
    description: 'Field sort order',
  })
  createdAt?: Prisma.SortOrder;

  @Field(() => UserProfileOrderByInput, {
    nullable: true,
    description: 'Field sort order',
  })
  profile?: UserProfileOrderByInput;
}
