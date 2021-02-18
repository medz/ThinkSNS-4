import { Injectable } from '@nestjs/common';
import { PrismaClient, SecuritySmsCode } from '@prisma/client';
import * as dayjs from 'dayjs';
import { IDHelper } from 'src/helper';
import { TencentCloudSmsService } from 'src/tencent-cloud/sms';

/**
 * Security SMS service.
 */
@Injectable()
export class SecuritySmsService {
  /**
   * Create the service.
   * @param tencentCloudSmsService Tencent Cloud SMS service.
   * @param prisma Prisma client.
   */
  constructor(
    private readonly tencentCloudSmsService: TencentCloudSmsService,
    private readonly prisma: PrismaClient,
  ) {}

  /**
   * Get SMS options.
   * @param hasChina Has china.
   */
  getOptions(hasChina: boolean) {
    const expiredIn = Number.parseInt(
      process.env.TENCENT_CLOUD_SMS_EXPIRED_IN || '300',
    );
    if (hasChina) {
      return {
        expiredIn,
        templateID: process.env.TENCENT_CLOUD_SMS_CHINA_TEMPLATE_ID,
        veriables: process.env.TENCENT_CLOUD_SMS_CHINA_VERIABLES,
      };
    }

    return {
      expiredIn,
      templateID: process.env.TENCENT_CLOUD_SMS_OTHER_TEMPLATE_ID,
      veriables: process.env.TENCENT_CLOUD_SMS_OTHER_VERIABLES,
    };
  }

  /**
   * Send a SMS code to phone.
   * @param phone Send phone number.
   */
  async send(phone: string) {
    const hasChina = phone.startsWith('+86');
    const options = this.getOptions(hasChina);
    const code = IDHelper.numeral(6);
    const expiredAt = dayjs().add(options.expiredIn, 'seconds').toDate();
    const paramsSet = options.veriables
      .split(',')
      .map((item) =>
        item
          .replace('#code#', code)
          .replace('#expired#', (options.expiredIn / 60).toString()),
      );

    const security = await this.prisma.securitySmsCode.create({
      data: {
        id: IDHelper.id(64),
        phone,
        code,
        expiredAt,
      },
    });

    this.tencentCloudSmsService.send({
      PhoneNumberSet: [phone],
      TemplateID: options.templateID,
      TemplateParamSet: paramsSet,
    });

    return security;
  }

  /**
   * Compase phone security code.
   * @param phone phone number.
   * @param code The sent phone security SMS code.
   */
  async compareCode(
    phone: string,
    code: string,
  ): Promise<false | SecuritySmsCode> {
    if (!phone || !code) {
      return false;
    }

    const security = await this.prisma.securitySmsCode.findFirst({
      where: { phone, code },
      orderBy: { createdAt: 'desc' },
      rejectOnNotFound: false,
    });
    if (
      security &&
      !security.usedAt &&
      security.expiredAt.getTime() > Date.now()
    ) {
      return security;
    }

    return false;
  }

  /**
   * Update security to used.
   * @param security Security code.
   */
  async updateCodeToUsed(security: SecuritySmsCode) {
    return await this.prisma.securitySmsCode.update({
      where: { id: security.id },
      data: { usedAt: new Date() },
    });
  }
}
