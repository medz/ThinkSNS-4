import { registerEnumType } from '@nestjs/graphql';

/**
 * User security compare type.
 */
export enum UserSecurityCompareType {
  // password
  PASSWORD,
  // Phone SMS code
  PHONE_SMS_CODE,
}

/**
 * Register enum to GraphQL schema.
 */
registerEnumType(UserSecurityCompareType, {
  name: 'UserSecurityCompareType',
  description: 'User security compare type',
});
