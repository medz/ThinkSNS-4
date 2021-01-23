import { Field, InputType, registerEnumType } from '@nestjs/graphql';
import { Prisma } from '@socfony/prisma';
import { UserWhereUniqueInput } from '../../user/dto/user-where-unique.input';

/**
 * User create Authorization login type.
 */
export enum LoginType {
  PASSWORD,
  PHONE_CODE,
}

// Register enum to GraphQL schema.
registerEnumType(LoginType, {
  name: 'LoginType',
  description: 'User login type',
});

/**
 * User create authorization login input.
 */
@InputType({
  description: 'User create authorization login input.',
})
export class LoginInput {
  /**
   * Login user where unique input.
   */
  @Field((type) => UserWhereUniqueInput, {
    description: 'User where unique input',
  })
  account: Prisma.UserWhereUniqueInput;

  /**
   * User login type.
   */
  @Field((type) => LoginType, {
    description: 'User login type.',
  })
  type: LoginType;

  /**
   * User login password or security code
   */
  @Field((type) => String, {
    description: 'User login password or security code',
  })
  encrypted: string;
}
