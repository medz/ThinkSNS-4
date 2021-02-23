import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserWhereInput } from './user-where.input';

@InputType({
  description: 'User Relation filter',
})
export class UserRelationFilter implements Prisma.UserRelationFilter {
  @Field(() => UserWhereInput, {
    nullable: true,
    description: 'User relation filter is',
  })
  is?: UserWhereInput;

  @Field(() => UserWhereInput, {
    nullable: true,
    description: 'User relation filter is not',
  })
  isNot?: UserWhereInput;
}
