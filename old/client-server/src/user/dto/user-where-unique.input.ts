import { Field, ID, InputType } from '@nestjs/graphql';
import { Prisma } from '@socfony/prisma';

/**
 * User wherer unique input
 */
@InputType({
  description: 'User wherer unique input',
})
export class UserWhereUniqueInput implements Prisma.UserWhereUniqueInput {
  /**
   * User ID.
   */
  @Field((type) => ID, {
    nullable: true,
    description: 'User ID',
  })
  id?: string;

  /**
   * User login name.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User login name',
  })
  login?: string;

  /**
   * User E-Mail address.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'User E-Mail address',
  })
  email?: string;

  /**
   * user phone number.
   */
  @Field((type) => String, {
    nullable: true,
    description: 'user phone number',
  })
  phone?: string;
}
