import { Field, InputType, registerEnumType } from '@nestjs/graphql';
import { Prisma } from '@prisma/client';

registerEnumType(Prisma.QueryMode, {
  name: 'QueryMode',
});

/**
 * String filter
 */
@InputType({
  description: 'String filter',
})
export class StringFilter implements Prisma.StringFilter {
  @Field(() => String, { nullable: true, description: 'String equals' })
  equals?: string;

  @Field(() => [String], { nullable: true, description: 'String in' })
  in?: string[];

  @Field(() => [String], { nullable: true, description: 'String not in' })
  notIn?: string[];

  @Field(() => String, { nullable: true, description: 'String lt' })
  lt?: string;

  @Field(() => String, { nullable: true, description: 'String lte' })
  lte?: string;

  @Field(() => String, { nullable: true, description: 'String gt' })
  gt?: string;

  @Field(() => String, { nullable: true, description: 'String gte' })
  gte?: string;

  @Field(() => String, { nullable: true, description: 'String contains' })
  contains?: string;

  @Field(() => String, { nullable: true, description: 'String starts with' })
  startsWith?: string;

  @Field(() => String, { nullable: true, description: 'String end with' })
  endsWith?: string;

  @Field(() => Prisma.QueryMode, { nullable: true, description: 'query mode' })
  mode?: Prisma.QueryMode;

  @Field(() => StringFilter, { nullable: true, description: 'not' })
  not?: StringFilter;
}
