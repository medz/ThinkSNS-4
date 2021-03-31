import { Field, InputType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';

/**
 * String filter
 */
@InputType({
  description: 'String filter',
})
export class StringFilter implements Prisma.StringFilter {
  /**
   * String equals
   */
  @Field(() => String, { nullable: true, description: 'String equals' })
  equals?: string;

  /**
   * String in
   */
  @Field(() => [String], { nullable: true, description: 'String in' })
  in?: string[];

  /**
   * String not in
   */
  @Field(() => [String], { nullable: true, description: 'String not in' })
  notIn?: string[];

  /**
   * String lt
   */
  @Field(() => String, { nullable: true, description: 'String lt' })
  lt?: string;

  /**
   * String lte
   */
  @Field(() => String, { nullable: true, description: 'String lte' })
  lte?: string;

  /**
   * String gt
   */
  @Field(() => String, { nullable: true, description: 'String gt' })
  gt?: string;

  /**
   * String gte
   */
  @Field(() => String, { nullable: true, description: 'String gte' })
  gte?: string;

  /**
   * String contains
   */
  @Field(() => String, { nullable: true, description: 'String contains' })
  contains?: string;

  /**
   * String starts with
   */
  @Field(() => String, { nullable: true, description: 'String starts with' })
  startsWith?: string;

  /**
   * String end with
   */
  @Field(() => String, { nullable: true, description: 'String end with' })
  endsWith?: string;

  /**
   * query mode
   */
  @Field(() => Prisma.QueryMode, { nullable: true, description: 'query mode' })
  mode?: Prisma.QueryMode;

  /**
   * not
   */
  @Field(() => StringFilter, { nullable: true, description: 'not' })
  not?: StringFilter;
}
