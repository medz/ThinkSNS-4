import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { DateTimeFilter, StringFilter } from 'src/graphql';
import { MomentListRelationFilter } from 'src/moment/dto/moment-list-relation.filter';
import { UserProfileRelationFilter } from '../profile/dto/user-profile-relation.filter';

/**
 * User where input
 */
@InputType({
  description: 'User where input',
})
export class UserWhereInput
  implements Omit<Prisma.UserWhereInput, 'password' | 'authorizationTokens'> {
  /**
   * AND where
   */
  @Field(() => [UserWhereInput], { nullable: true, description: 'AND where' })
  AND?: UserWhereInput[];

  /**
   * OR where
   */
  @Field(() => [UserWhereInput], { nullable: true, description: 'OR where' })
  OR?: UserWhereInput[];

  /**
   * NOT where
   */
  @Field(() => [UserWhereInput], { nullable: true, description: 'NOT where' })
  NOT?: UserWhereInput[];

  /**
   * ID filter
   */
  @Field(() => StringFilter, { nullable: true, description: 'ID filter' })
  id?: StringFilter;

  /**
   * Username filter
   */
  @Field(() => StringFilter, {
    nullable: true,
    description: 'username filter',
  })
  username?: StringFilter;

  /**
   * user phone number filter
   */
  @Field(() => StringFilter, {
    nullable: true,
    description: 'user phone number filter',
  })
  phone?: StringFilter;

  /**
   * created at date time filter
   */
  @Field(() => DateTimeFilter, {
    nullable: true,
    description: 'created at date time filter',
  })
  createdAt?: DateTimeFilter;

  /**
   * user profile relation filter
   */
  @Field(() => UserProfileRelationFilter, {
    nullable: true,
    description: 'user profile relation filter',
  })
  profile?: UserProfileRelationFilter;

  /**
   * user moment list relation filter
   */
  @Field(() => UserProfileRelationFilter, {
    nullable: true,
    description: 'user moment list relation filter',
  })
  moments?: MomentListRelationFilter;
}
