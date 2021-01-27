import { ArgsType, Field, registerEnumType } from '@nestjs/graphql';
import { UserWhereUniqueInput } from 'src/user';

export enum CreateAuthorizationTokenSecurityType {
  PASSWORD,
  SMS_CODE,
}

registerEnumType(CreateAuthorizationTokenSecurityType, {
  name: 'CreateAuthorizationTokenSecurityType',
  description: 'Create AuthorizationToken security type',
});

@ArgsType()
export class CreateAuthorizationTokenArgs {
  @Field(() => UserWhereUniqueInput, {
    description: 'User find where unique input',
  })
  user: UserWhereUniqueInput;

  @Field(() => CreateAuthorizationTokenSecurityType, {
    description: 'Create AuthorizationToken security type',
  })
  type: CreateAuthorizationTokenSecurityType;

  @Field(() => String, {
    description: 'Create AuthorizationToken security value',
  })
  security: string;
}
