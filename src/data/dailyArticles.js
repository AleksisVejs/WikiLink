/**
 * Curated pool of well-known Wikipedia article titles used for the daily challenge.
 * The daily seed picks two different titles from this list — no Wikipedia search
 * is involved in selection, so every player gets the exact same pair.
 *
 * Titles must match Wikipedia exactly (case-sensitive, underscores = spaces).
 * Add or remove entries freely; order matters only for seed stability across deploys.
 */
const DAILY_ARTICLES = [
  // Science
  'Albert Einstein', 'Isaac Newton', 'Nikola Tesla', 'Marie Curie', 'Charles Darwin',
  'Galileo Galilei', 'DNA', 'Photosynthesis', 'Black hole', 'General relativity',
  'Quantum mechanics', 'Periodic table', 'Speed of light', 'Higgs boson', 'Mitochondrion',
  'Theory of evolution', 'Plate tectonics', 'Solar System', 'Atom', 'Electron',
  'Thermodynamics', 'Hubble Space Telescope', 'Penicillin', 'Virus', 'Bacteria',

  // History
  'World War II', 'World War I', 'Roman Empire', 'Ancient Egypt', 'French Revolution',
  'Renaissance', 'Cold War', 'Industrial Revolution', 'Byzantine Empire', 'Mongol Empire',
  'Ottoman Empire', 'Aztec Empire', 'Viking Age', 'Silk Road', 'Spanish Inquisition',
  'American Revolution', 'Berlin Wall', 'Magna Carta', 'Battle of Stalingrad', 'Crusades',
  'Alexander the Great', 'Cleopatra', 'Julius Caesar', 'Napoleon', 'Genghis Khan',

  // Geography
  'Mount Everest', 'Amazon River', 'Sahara', 'Great Barrier Reef', 'Grand Canyon',
  'Antarctica', 'Amazon rainforest', 'Nile', 'Pacific Ocean', 'Mariana Trench',
  'Yellowstone National Park', 'Iceland', 'Madagascar', 'Galápagos Islands', 'Dead Sea',
  'Mount Kilimanjaro', 'Niagara Falls', 'Great Wall of China', 'Bermuda Triangle', 'Panama Canal',

  // Countries & Cities
  'Japan', 'Brazil', 'Australia', 'India', 'Norway',
  'New Zealand', 'South Africa', 'Mexico', 'Egypt', 'Canada',
  'Rome', 'Tokyo', 'Paris', 'Istanbul', 'New York City',
  'London', 'Cairo', 'Sydney', 'Rio de Janeiro', 'Moscow',

  // Sports
  'Olympic Games', 'FIFA World Cup', 'Tour de France', 'Super Bowl', 'Wimbledon Championships',
  'Muhammad Ali', 'Michael Jordan', 'Pelé', 'Usain Bolt', 'Serena Williams',
  'Cricket World Cup', 'Formula One', 'Tennis', 'Basketball', 'Marathon',
  'Chess', 'Lionel Messi', 'Cristiano Ronaldo', 'Baseball', 'Ice hockey',

  // Nature
  'African elephant', 'Blue whale', 'Great white shark', 'Tyrannosaurus', 'Bald eagle',
  'Giant panda', 'Komodo dragon', 'Octopus', 'Honey bee', 'Coral reef',
  'Penguin', 'Dolphin', 'Wolf', 'Lion', 'Cheetah',
  'Redwood', 'Venus flytrap', 'Orchid', 'Rainforest', 'Glacier',

  // Music
  'The Beatles', 'Wolfgang Amadeus Mozart', 'Ludwig van Beethoven', 'Elvis Presley', 'Michael Jackson',
  'Bob Marley', 'Freddie Mercury', 'Johann Sebastian Bach', 'Jazz', 'Hip hop music',
  'Piano', 'Guitar', 'Symphony', 'Opera', 'Rock and roll',
  'David Bowie', 'Pink Floyd', 'Violin', 'Blues', 'Electronic music',

  // Movies & TV
  'Star Wars', 'The Godfather', 'Titanic (1997 film)', 'The Shawshank Redemption', '2001: A Space Odyssey (film)',
  'Alfred Hitchcock', 'Steven Spielberg', 'Walt Disney', 'Academy Awards', 'Studio Ghibli',
  'Stanley Kubrick', 'Pixar', 'James Bond', 'The Simpsons', 'Jurassic Park',
  'Marvel Cinematic Universe', 'The Lord of the Rings (film series)', 'Charlie Chaplin', 'Cinema of Japan', 'Animation',

  // Technology
  'Internet', 'World Wide Web', 'Artificial intelligence', 'Computer', 'Smartphone',
  'Space exploration', 'International Space Station', 'Moon landing', 'Mars rover', 'Satellite',
  'Printing press', 'Telephone', 'Television', 'Radio', 'Steam engine',
  'Nuclear power', 'Electric car', 'Cryptocurrency', 'Robotics', 'Microprocessor',

  // Food & Drink
  'Coffee', 'Chocolate', 'Sushi', 'Pizza', 'Wine',
  'Bread', 'Cheese', 'Tea', 'Beer', 'Olive oil',
  'Spice trade', 'Curry', 'Pasta', 'Rice', 'Vanilla',
  'Whisky', 'Honey', 'Maple syrup', 'Avocado', 'Banana',

  // Art & Culture
  'Mona Lisa', 'Leonardo da Vinci', 'Michelangelo', 'Pablo Picasso', 'Vincent van Gogh',
  'Frida Kahlo', 'Ancient Greek architecture', 'Gothic architecture', 'Baroque', 'Impressionism',
  'Photography', 'Sculpture', 'Graffiti', 'Origami', 'Calligraphy',

  // Literature & Philosophy
  'William Shakespeare', 'Hamlet', 'Homer', 'The Odyssey', 'Don Quixote',
  'Plato', 'Aristotle', 'Socrates', 'Library of Alexandria', 'Gutenberg Bible',
  'Frankenstein', 'George Orwell', 'Mark Twain', 'Jane Austen', 'Leo Tolstoy',

  // Miscellaneous
  'Pyramids of Giza', 'Stonehenge', 'Machu Picchu', 'Colosseum', 'Taj Mahal',
  'Titanic', 'Hindenburg disaster', 'Apollo 11', 'Pompeii', 'Chernobyl disaster',
  'Mathematics', 'Pi', 'Golden ratio', 'Fibonacci sequence', 'Prime number',
  'Human brain', 'Heart', 'Blood', 'Immune system', 'Sleep',
]

export default DAILY_ARTICLES
