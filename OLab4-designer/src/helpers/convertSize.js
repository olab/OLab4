// @flow
const convert = (
  size: number,
  degree: number,
  dimension: string,
): string => `${(size / degree).toFixed(1)}${dimension}`;

const convertSize = (size: number): string => {
  const TB = { name: 'TB', value: 2 ** 40 };
  const GB = { name: 'GB', value: 2 << 29 };
  const MB = { name: 'MB', value: 2 << 19 };
  const KB = { name: 'KB', value: 2 << 9 };

  const measure = [TB, GB, MB, KB].find(({ value }) => size >= value);

  if (measure) {
    return convert(size, measure.value, measure.name);
  }

  return `${size}B`;
};

export default convertSize;
