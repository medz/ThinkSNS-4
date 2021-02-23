import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';
import { DateTimeFilter, StringFilter } from 'src/graphql';
import { UserRelationFilter } from 'src/user/dto/user-relation.filter';

@InputType()
export class MomentWhereInput
  implements Omit<Prisma.MomentWhereInput, 'media' | 'deletedAt'> {
  @Field(() => [MomentWhereInput], { description: 'AND where', nullable: true })
  AND?: MomentWhereInput[];

  @Field(() => [MomentWhereInput], { description: 'OR where', nullable: true })
  OR?: MomentWhereInput[];

  @Field(() => [MomentWhereInput], { description: 'NOT where', nullable: true })
  NOT?: MomentWhereInput[];

  @Field(() => StringFilter, {
    description: 'ID string filter',
    nullable: true,
  })
  id?: StringFilter;

  @Field(() => StringFilter, { description: 'title filter', nullable: true })
  title?: StringFilter;

  @Field(() => StringFilter, { description: 'content filter', nullable: true })
  content?: StringFilter;

  @Field(() => StringFilter, {
    description: 'created at date time filter',
    nullable: true,
  })
  createdAt?: DateTimeFilter;

  @Field(() => StringFilter, {
    description: 'owner ID string filter',
    nullable: true,
  })
  ownerId?: StringFilter | string;

  @Field(() => UserRelationFilter, {
    description: 'owner filter',
    nullable: true,
  })
  owner?: UserRelationFilter;
}
