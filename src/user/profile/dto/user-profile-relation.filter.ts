import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { UserProfileWhereInput } from './user-profile-where.input';

@InputType({ description: 'User profile where filter' })
export class UserProfileRelationFilter
  implements Prisma.UserProfileRelationFilter {
  @Field(() => UserProfileWhereInput, {
    nullable: true,
    description: 'is where input',
  })
  is?: UserProfileWhereInput;

  @Field(() => UserProfileWhereInput, {
    nullable: true,
    description: 'is not where input',
  })
  isNot?: UserProfileWhereInput;
}
