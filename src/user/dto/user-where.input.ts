import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { DateTimeFilter, StringFilter } from 'src/graphql';
import { MomentListRelationFilter } from 'src/moment/dto/moment-list-relation.filter';
import { UserProfileRelationFilter } from '../profile/dto/user-profile-relation.filter';

@InputType({
  description: 'User where input',
})
export class UserWhereInput
  implements Omit<Prisma.UserWhereInput, 'password' | 'authorizationTokens'> {
  @Field(() => [UserWhereInput], { nullable: true, description: 'AND where' })
  AND?: UserWhereInput[];

  @Field(() => [UserWhereInput], { nullable: true, description: 'OR where' })
  OR?: UserWhereInput[];

  @Field(() => [UserWhereInput], { nullable: true, description: 'NOT where' })
  NOT?: UserWhereInput[];

  @Field(() => StringFilter, { nullable: true, description: 'ID filter' })
  id?: StringFilter;

  @Field(() => StringFilter, {
    nullable: true,
    description: 'login name filter',
  })
  login?: StringFilter;

  @Field(() => StringFilter, { nullable: true, description: 'E-Mail filter' })
  email?: StringFilter;

  @Field(() => StringFilter, {
    nullable: true,
    description: 'user phone numberfilter',
  })
  phone?: StringFilter;

  @Field(() => DateTimeFilter, {
    nullable: true,
    description: 'created at date time filter',
  })
  createdAt?: DateTimeFilter;

  @Field(() => UserProfileRelationFilter, {
    nullable: true,
    description: 'user profile relation filter',
  })
  profile?: UserProfileRelationFilter;

  @Field(() => UserProfileRelationFilter, {
    nullable: true,
    description: 'user moment list relation filter',
  })
  moments?: MomentListRelationFilter;
}
