import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { StringFilter } from 'src/graphql';
import { UserRelationFilter } from 'src/user/dto/user-relation.filter';

@InputType({ description: 'User profile where input' })
export class UserProfileWhereInput
  implements Omit<Prisma.UserProfileWhereInput, 'updatedAt'> {
  @Field(() => [UserProfileWhereInput], {
    nullable: true,
    description: 'AND where',
  })
  AND?: UserProfileWhereInput[];

  @Field(() => [UserProfileWhereInput], {
    nullable: true,
    description: 'OR where',
  })
  OR?: UserProfileWhereInput[];

  @Field(() => [UserProfileWhereInput], {
    nullable: true,
    description: 'NOT where',
  })
  NOT?: UserProfileWhereInput[];

  @Field(() => StringFilter, { nullable: true, description: 'User ID filter' })
  userId?: StringFilter;

  @Field(() => UserRelationFilter, {
    nullable: true,
    description: 'User relation filter',
  })
  user?: UserRelationFilter;

  @Field(() => StringFilter, { nullable: true, description: 'User ID filter' })
  name?: StringFilter;

  @Field(() => StringFilter, { nullable: true, description: 'User ID filter' })
  avatar?: StringFilter;

  @Field(() => StringFilter, { nullable: true, description: 'User ID filter' })
  bio?: StringFilter;

  @Field(() => StringFilter, { nullable: true, description: 'User ID filter' })
  location?: StringFilter;
}
