import { registerEnumType } from '@nestjs/graphql';

export enum UserSecurityCompareType {
  PASSWORD,
  SMS_CODE,
}

registerEnumType(UserSecurityCompareType, {
  name: 'UserSecurityCompareType',
  description: 'User security compare type',
});
