import { Field, InputType } from '@nestjs/graphql';
import { SecurityCode } from '@socfony/prisma';

/**
 * Send security code input.
 */
@InputType({
  description: 'Send security code input.',
})
export class SendSecurityCodeInput
  implements Pick<SecurityCode, 'validateSender' | 'account'> {
  /**
   * Need send security code account.
   */
  @Field((type) => String, {
    description: 'Need send security code account.',
  })
  account: string;

  /**
   * Has sent security code need validate sender.
   */
  @Field((type) => Boolean, {
    description: 'Has sent security code need validate sender.',
  })
  validateSender: boolean;
}
