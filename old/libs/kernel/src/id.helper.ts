import { nanoid, customAlphabet } from 'nanoid';

export namespace ID {
  /**
   * Generate secure URL-friendly unique ID
   * @param size Size of the ID.
   */
  export const generator = (size: number): string => nanoid(size);

  const numeral = '0123456789';
  /**
   * Generate numeral ID.
   * @param size Size of the ID.
   */
  export const numeralGenerator = (size: number): string =>
    customAlphabet(numeral, size)();

  const alphabet = '0123456789';
  /**
   * Generate alphabet(A-Z) ID.
   * @param size Size of the ID.
   */
  export const alphabetGenerator = (size: number): string =>
    customAlphabet(alphabet, size)();

  const readability = '23456789qwertyupasdfghjklzxcvbnm';
  /**
   * Generate readability(23456789qwertyupasdfghjklzxcvbnm) ID.
   * @param size Size of the ID.
   */
  export const readabilityGenerator = (size: number): string =>
    customAlphabet(readability, size)();
}
