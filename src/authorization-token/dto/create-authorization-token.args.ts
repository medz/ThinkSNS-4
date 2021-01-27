import { ArgsType, Field } from '@nestjs/graphql';
import { UserWhereUniqueInput } from 'src/user/dto';
import { UserSecurityCompareType } from 'src/user/enums';

@ArgsType()
export class CreateAuthorizationTokenArgs {
  @Field(() => UserWhereUniqueInput, {
    description: 'User find where unique input',
  })
  user: UserWhereUniqueInput;

  @Field(() => UserSecurityCompareType, {
    description: 'Create AuthorizationToken security type',
  })
  type: UserSecurityCompareType;

  @Field(() => String, {
    description: 'Create AuthorizationToken security value',
  })
  security: string;
}
