import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { StringFilter } from 'src/graphql';
import { UserRelationFilter } from 'src/user/dto/user-relation.filter';

/**
 * User profile where input
 */
@InputType({ description: 'User profile where input' })
export class UserProfileWhereInput
  implements Omit<Prisma.UserProfileWhereInput, 'updatedAt'> {
  /**
   * AND where
   */
  @Field(() => [UserProfileWhereInput], {
    nullable: true,
    description: 'AND where',
  })
  AND?: UserProfileWhereInput[];

  /**
   * OR where
   */
  @Field(() => [UserProfileWhereInput], {
    nullable: true,
    description: 'OR where',
  })
  OR?: UserProfileWhereInput[];

  /**
   * NOT where
   */
  @Field(() => [UserProfileWhereInput], {
    nullable: true,
    description: 'NOT where',
  })
  NOT?: UserProfileWhereInput[];

  /**
   * User ID filter
   */
  @Field(() => StringFilter, { nullable: true, description: 'User ID filter' })
  userId?: StringFilter;

  /**
   * User relation filter
   */
  @Field(() => UserRelationFilter, {
    nullable: true,
    description: 'User relation filter',
  })
  user?: UserRelationFilter;

  /**
   * User name filter
   */
  @Field(() => StringFilter, {
    nullable: true,
    description: 'User name filter',
  })
  name?: StringFilter;

  /**
   * User avatar filter
   */
  @Field(() => StringFilter, {
    nullable: true,
    description: 'User avatar filter',
  })
  avatar?: StringFilter;

  /**
   * User bio filter
   */
  @Field(() => StringFilter, { nullable: true, description: 'User bio filter' })
  bio?: StringFilter;

  /**
   * User location filter
   */
  @Field(() => StringFilter, {
    nullable: true,
    description: 'User location filter',
  })
  location?: StringFilter;
}
