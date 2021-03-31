import { Field, GraphQLISODateTime, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';

/**
 * Date time filter
 */
@InputType({
  description: 'Date time filter',
})
export class DateTimeFilter implements Prisma.DateTimeFilter {
  /**
   * Date equals
   */
  @Field(() => GraphQLISODateTime, {
    description: 'Date equals',
    nullable: true,
  })
  equals?: Date;

  /**
   * Date in
   */
  @Field(() => [GraphQLISODateTime], { description: 'Date in', nullable: true })
  in?: Date[];

  @Field(() => [GraphQLISODateTime], {
    description: 'Date not in',
    nullable: true,
  })
  notIn?: Date[];

  /**
   * Date lt
   */
  @Field(() => GraphQLISODateTime, { description: 'Date lt', nullable: true })
  lt?: Date;

  /**
   * Date lte
   */
  @Field(() => GraphQLISODateTime, { description: 'Date lte', nullable: true })
  lte?: Date;

  /**
   * Date gt
   */
  @Field(() => GraphQLISODateTime, { description: 'Date gt', nullable: true })
  gt?: Date;

  /**
   * Date gte
   */
  @Field(() => GraphQLISODateTime, { description: 'Date gte', nullable: true })
  gte?: Date;

  /**
   * Date not
   */
  @Field(() => DateTimeFilter, { description: 'Date not', nullable: true })
  not?: DateTimeFilter;
}
