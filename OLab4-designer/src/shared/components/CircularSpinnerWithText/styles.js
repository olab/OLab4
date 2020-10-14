import styled, { css } from 'styled-components';

export const ProgressWrapper = styled.div`
  display: flex;
  align-items: center;
  vertical-align: center;
  position: absolute;
  bottom: 0;

  ${({ centered }) => centered && css`
    flex-direction: column;
    left: 50%;
    top: 50%;
    height: 10vh;
    transform: translate(-50%, -50%);
  `}
`;

const styles = () => ({
  spinnerCaption: {
    marginLeft: 5,
  },
  centeredText: {
    fontSize: '1.25rem',
    marginTop: 15,
  },
});

export default styles;
