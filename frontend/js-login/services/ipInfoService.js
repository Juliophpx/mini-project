export function getCountryFromIP() {
  return fetch('https://juliophp.com/api-universal-login/ipinfo')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    });
}
