import styled from 'styled-components';

export const ScaleIconWrapper = styled.div`
  display: block;
  margin-right: 5px;

  &:hover {
    filter: sepia() saturate(10000%) hue-rotate(200deg);
  }
`;

export const ZoomContainer = styled.span`
  margin-right: 5px;
`;

export const ZoomWrapper = styled.span`
  display: flex;
  align-items: center;
`;

const styles = theme => ({
  arrow: {
    position: 'absolute',
    fontSize: 7,
    height: '3em',
    top: 0,
    marginTop: '-0.9em',
    width: '3em',
    left: '50%',
    transform: 'translate(-50%)',
    '&::before': {
      content: '""',
      margin: 'auto',
      display: 'block',
      width: 0,
      height: 0,
      borderStyle: 'solid',
      borderWidth: '0 1em 1em 1em',
      borderColor: `transparent transparent ${theme.palette.common.white} transparent`,
    },
  },
  sliderWidth: {
    minWidth: 150,
    maxWidth: 150,
    padding: 10,
    boxSizing: 'border-box',
  },
});

export default styles;
