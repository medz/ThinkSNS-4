import { ArgsType, Field } from '@nestjs/graphql';
import { UserWhereUniqueInput } from 'src/user/dto/user-where-unique.input';
import { UserSecurityCompareType } from 'src/user/enums';

/**
 * Create authorization token args
 */
@ArgsType()
export class CreateAuthorizationTokenArgs {
  /**
   * User find where unique input
   */
  @Field(() => UserWhereUniqueInput, {
    description: 'User find where unique input',
  })
  user: UserWhereUniqueInput;

  /**
   * Create AuthorizationToken security type
   */
  @Field(() => UserSecurityCompareType, {
    description: 'Create AuthorizationToken security type',
  })
  type: UserSecurityCompareType;

  /**
   * Create AuthorizationToken security value
   */
  @Field(() => String, {
    description: 'Create AuthorizationToken security value',
  })
  security: string;
}
