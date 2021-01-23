import { Args, Mutation, Resolver } from '@nestjs/graphql';
import { Authorization, HasTokenExpiredType } from '@socfony/auth';
import { Prisma } from '@socfony/prisma';
import { SecurityCodeService } from '@socfony/security-code';
import { UNAUTHORIZED } from '@socfony/error-code';
import { SendSecurityCodeInput } from './dto';
import { Context } from '@socfony/kernel';

/**
 * Security code sender resolver.
 */
@Resolver()
export class SecurityCodeResolver {
  constructor(
    private readonly securityService: SecurityCodeService,
    private readonly context: Context,
  ) {}

  /**
   * Send security code.
   * @param data Send security code input.
   * @param userPromise The HTTP endpoint `Authorization` code bount user Prisma query client.
   */
  @Mutation((returns) => Boolean, {
    nullable: true,
    description: 'Send security code.',
  })
  @Authorization({
    hasAuthorization: true,
    type: HasTokenExpiredType.AUTH,
  })
  async sendSecurityCode(
    @Args({
      name: 'data',
      type: () => SendSecurityCodeInput,
    })
    data: Prisma.SecurityCodeCreateInput,
  ) {
    if (data.validateSender && !this.context.user) {
      throw new Error(UNAUTHORIZED);
    } else if (data.validateSender) {
      data.sender = {
        connect: { id: this.context.user.id },
      };
    }
    this.securityService.send(data);
  }
}
