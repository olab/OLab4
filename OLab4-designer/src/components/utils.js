// @flow
export const redirectToPlayer = (
  mapId: number,
  nodeId: number,
): Function => (): void => {
  window.open(`${process.env.PLAYER_PUBLIC_URL}/olab/play#${mapId}:${nodeId}`);
};

export default {
  redirectToPlayer,
};
