import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserWhereInput } from './user-where.input';

/**
 * User Relation filter
 */
@InputType({
  description: 'User Relation filter',
})
export class UserRelationFilter implements Prisma.UserRelationFilter {
  /**
   * User relation filter is
   */
  @Field(() => UserWhereInput, {
    nullable: true,
    description: 'User relation filter is',
  })
  is?: UserWhereInput;

  /**
   * User relation filter is not
   */
  @Field(() => UserWhereInput, {
    nullable: true,
    description: 'User relation filter is not',
  })
  isNot?: UserWhereInput;
}
