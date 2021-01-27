import { customAlphabet, nanoid } from 'nanoid';

export namespace IDHelper {
  /**
   * Generate secure URL-friendly unique ID
   * @param size Size of the ID.
   */
  export const id = (size: number): string => nanoid(size);
  /**
   * Generate numeral ID.
   * @param size Size of the ID.
   */
  export const numeral = (size: number): string =>
    customAlphabet('0123456789', size)();

  /**
   * Generate alphabet(a-z) ID.
   * @param size Size of the ID.
   */
  export const alphabet = (size: number): string =>
    customAlphabet('qwertyuiopasdfghjklzxcvbnm', size)();

  /**
   * Generate readability(23456789qwertyupasdfghjklzxcvbnm) ID.
   * @param size Size of the ID.
   */
  export const readability = (size: number): string =>
    customAlphabet('23456789qwertyupasdfghjklzxcvbnm', size)();

  export const custom = (alphabet: string, size: number) =>
    customAlphabet(alphabet, size)();
}
