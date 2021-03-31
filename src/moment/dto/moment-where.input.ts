import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { DateTimeFilter, StringFilter } from 'src/graphql';
import { UserRelationFilter } from 'src/user/dto/user-relation.filter';

/**
 * Moment where input
 */
@InputType()
export class MomentWhereInput
  implements Omit<Prisma.MomentWhereInput, 'media' | 'deletedAt'> {
  /**
   * AND where
   */
  @Field(() => [MomentWhereInput], { description: 'AND where', nullable: true })
  AND?: MomentWhereInput[];

  /**
   * OR where
   */
  @Field(() => [MomentWhereInput], { description: 'OR where', nullable: true })
  OR?: MomentWhereInput[];

  /**
   * NOT where
   */
  @Field(() => [MomentWhereInput], { description: 'NOT where', nullable: true })
  NOT?: MomentWhereInput[];

  /**
   * ID string filter
   */
  @Field(() => StringFilter, {
    description: 'ID string filter',
    nullable: true,
  })
  id?: StringFilter;

  /**
   * title filter
   */
  @Field(() => StringFilter, { description: 'title filter', nullable: true })
  title?: StringFilter;

  /**
   * content filter
   */
  @Field(() => StringFilter, { description: 'content filter', nullable: true })
  content?: StringFilter;

  /**
   * created at date time filter
   */
  @Field(() => StringFilter, {
    description: 'created at date time filter',
    nullable: true,
  })
  createdAt?: DateTimeFilter;

  /**
   * owner ID string filter
   */
  @Field(() => StringFilter, {
    description: 'owner ID string filter',
    nullable: true,
  })
  ownerId?: StringFilter | string;

  /**
   * owner filter
   */
  @Field(() => UserRelationFilter, {
    description: 'owner filter',
    nullable: true,
  })
  owner?: UserRelationFilter;
}
