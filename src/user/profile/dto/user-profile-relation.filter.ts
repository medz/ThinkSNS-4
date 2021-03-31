import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserProfileWhereInput } from './user-profile-where.input';

/**
 * User profile where filter
 */
@InputType({ description: 'User profile where filter' })
export class UserProfileRelationFilter
  implements Prisma.UserProfileRelationFilter {
  /**
   * is where input
   */
  @Field(() => UserProfileWhereInput, {
    nullable: true,
    description: 'is where input',
  })
  is?: UserProfileWhereInput;

  /**
   * is not where input
   */
  @Field(() => UserProfileWhereInput, {
    nullable: true,
    description: 'is not where input',
  })
  isNot?: UserProfileWhereInput;
}
