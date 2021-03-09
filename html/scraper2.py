#!/usr/bin/python3
"""A tool to scrape Amazon for details of a book based on ISBN to reduce typing."""

import argparse
import logging
import re
import sys
import json
from selenium import webdriver
from selenium.common.exceptions import WebDriverException
from bs4 import BeautifulSoup

PAT_PUB1 = re.compile(r'(.*?); (\d*)[A-Za-z&,]* edition *\(.*?, *(\d\d\d\d)\)')
PAT_PUB2 = re.compile(r'(.*?) \(.*?, *(\d\d\d\d)\)')


TITLES = ['Mr.', 'Mrs.', 'Ms.', 'Prof.', 'Dr.', 'Fr.', 'Sr.', 'Br.']
CREDS = ['Ph.D.', 'M.D.', 'J.D.', 'D.D.', 'D.Phil.',
         'S.T.D.', 'S.T.B.', 'Jr.', 'Sr.', 'III', 'IV']
SPACE_CREDS = [s.replace('.', '. ').strip() for s in CREDS]

log = logging.getLogger('scraper')
log.setLevel(logging.DEBUG)
ch = logging.StreamHandler()
ch.setLevel(logging.DEBUG)
formatter = logging.Formatter('%(name)s:%(levelname)s:  %(message)s')
ch.setFormatter(formatter)
log.addHandler(ch)


class Person:
    """A representation of a person."""

    def __init__(self, name_string: str):
        name_string = name_string.replace(',', '')
        for space_cred, cred in zip(SPACE_CREDS, CREDS):
            name_string = name_string.replace(space_cred, cred)

        parts = name_string.strip().split(' ')

        while '' in parts:
            parts.remove('')

        if parts[0] == 'The':
            self.is_organization = True
            self.last_name = ' '.join(parts)
            self.first_and_middle_names = None
            self.titles = None
            self.credentials = None
            self.display_name = self.last_name
            self.sortable_name = self.last_name
        else:
            self.is_organization = False

            display_parts = []
            sortable_parts = []

            titles = []
            while parts[0] in TITLES:
                titles.append(parts.pop(0))
            self.titles = ', '.join(titles) if titles else None

            creds = []
            while parts[-1] in CREDS:
                creds.insert(0, parts.pop())
            self.credentials = ', '.join(creds) if creds else None

            self.last_name = parts.pop()

            if parts:
                self.first_and_middle_names = ' '.join(parts)
            else:
                self.first_and_middle_names = None

            sortable_parts = [self.last_name]
            display_parts = []

            if self.titles:
                display_parts.append(self.titles)
            if self.first_and_middle_names:
                sortable_parts.append(self.first_and_middle_names)
                display_parts.append(self.first_and_middle_names)
            display_parts.append(self.last_name)
            if self.credentials:
                display_parts[-1] += ','
                display_parts.append(self.credentials)
                sortable_parts.append(self.credentials)

            self.sortable_name = ', '.join(sortable_parts)
            self.display_name = ' '.join(display_parts)

    def get_dict(self):
        """ Return a dictionary representation of the person."""
        return {
            'lastName': self.last_name,
            'firstAndMiddleNames': self.first_and_middle_names,
            'titles': self.titles,
            'credentials': self.credentials,
            'isOrganization': self.is_organization,
            'displayName': self.display_name,
            'sortableName': self.sortable_name
        }


class Book:
    """A representation of Books on Amazon."""

    def __init__(self):
        self.title = ''
        self.subtitle = None
        self.price = 0.0
        self.authors = []
        self.publisher = None
        self.edition = 1
        self.year = 2001
        self.isbn10 = None
        self.isbn13 = None
        self.url = ''

    def add_author(self, author_string):
        """Add an author to the book."""
        self.authors.append(Person(author_string))

    def get_dict(self):
        """Return a dictionary representation of the object."""
        return {
            'title': self.title,
            'subtitle': self.subtitle,
            'price': self.price,
            'authors': [a.get_dict() for a in self.authors],
            'publisher': self.publisher,
            'edition': self.edition,
            'year': self.year,
            'isbn10': self.isbn10,
            'isbn13': self.isbn13,
            'url': self.url
        }


def get_driver(headless: bool = True):
    """Return a webdriver object."""
    if __debug__:
        log.debug('Attempting to load driver.')
    opts = webdriver.chrome.options.Options()
    if headless:
        opts.add_argument("--headless")
    try:
        driver = webdriver.Chrome(options=opts)
        if __debug__:
            log.debug('Driver successfully loaded.')
    except WebDriverException as err:
        log.error('Failed to load driver: %s.', err)
        sys.exit(150)
    return driver


def load_page(sub_url, headless: bool = True):
    """Return a BeautifulSoup object containing the page data.

    Parameters
    ----------
    sub_url : str
        The part of the URL that comes after https://www.amazon.com.
        It should probably begin with a slash.

    Returns
    -------
    BeautifulSoup
        The contents of the requested page.
    """
    url = f"https://www.amazon.com{sub_url}"
    log.info("Loading page <%s>.", url)
    driver = get_driver(headless)
    try:
        driver.get(url)
        contents = driver.page_source
        soup = BeautifulSoup(contents, 'lxml')
    except WebDriverException as err:
        log.error("Selenium threw error loading <%s>. Error: <%s>.", url, str(err))
        sys.exit(150)
    finally:
        driver.quit()
    return soup


def get_format_dict(soup):
    """Return a dict of the formats of the book available on Amazon."""
    result = {}
    button_region = soup.find('div', id="tmmSwatches")
    buttons = button_region.find_all('span', class_="a-button-inner")
    for button in buttons:
        link = button.find("a")
        link_text = link.find('span').get_text().strip()
        result[link_text.lower()] = link.get('href')
    return result


def get_first_result(isbn, headless: bool = True):
    """Pull the first result from Amazon.

    Parameters
    ----------
    isbn : str
        The ISBN to use for an Amazon search.

    Returns
    -------
    text : str
        The text of the link.
    url : str
        The URL to which the link points.
    """

    #sub_url = f"/s?isbn={isbn}"
    sub_url = f"/s?i=stripbooks&rh=p_66%3A{isbn}"
    soup = load_page(sub_url, headless)

    #log.info(soup)
    links = []
    for item in soup.find_all('h2'):
        for link in item.find_all('a'):
            links.append((link.get_text().strip(), link.get('href')))

    return links[0]


def get_book_data(sub_url, fmt=None, headless: bool = True):
    """Return a JSON string of book data from Amazon."""
    soup = load_page(sub_url, headless)

    book = Book()

    if fmt:
        available_formats = get_format_dict(soup)
        if fmt in available_formats:
            newlink = available_formats[fmt]
            log.info("Redirecting to different format <%s>.", fmt)
            soup = load_page(newlink)
        else:
            log.info("Requested format not available")

    title = soup.find(id='productTitle').get_text().strip()
    subtitle = None
    if ':' in title:
        title, subtitle = title.split(': ', maxsplit=1)
    log.info("Found title %s.", title)
    log.info("Found subtitle %s.", subtitle)
    book.title = title
    book.subtitle = subtitle

    try:
        price = soup.find(id='listPrice').get_text().strip()
        if __debug__:
            log.debug("Found listPrice element.")
    except AttributeError:
        try:
            price = soup.find(id='newBuyBoxPrice').get_text().strip()
            if __debug__:
                log.debug("Found newBuyBoxPrice element.")
        except AttributeError:
            log.warning("No price found. Dumping page.")
            with open('dump_price_search.html', 'w') as outfile:
                outfile.write(str(soup))
            price = ''
    price = price.replace('$', '')
    book.price = float(price)
    log.info("Using price: %f", float(price))

    author_area = soup.find_all('span', class_="author")
    for author in author_area:
        for item in author.find_all('a'):
            if item.find_parents(class_='a-popover-preload'):
                continue
            text = cleantext(item)
            if text:
                book.add_author(text)

    #details = soup.find(id='productDetailsTable')
    details = soup.find(id='detailBullets_feature_div')
    for item in details.find_all('li'):
        itemstr = str(item)
        if 'Publisher' in itemstr:
            pub_full = cleantext(item).replace('Publisher: ', '')
            match = PAT_PUB1.search(pub_full)
            if match:
                book.publisher = match.group(1)
                try:
                    book.edition = int(match.group(2))
                except ValueError:
                    book.edition = match.group(2)
                book.year = int(match.group(3))
            else:
                match = PAT_PUB2.search(pub_full)
                if match:
                    book.publisher = match.group(1)
                    book.year = int(match.group(2))
                else:
                    book.publisher = pub_full
                book.edition = '1'
        elif 'ISBN-10' in itemstr:
            book.isbn10 = cleantext(item).replace('ISBN-10', '').replace(':', '').strip()
        elif 'ISBN-13' in itemstr:
            book.isbn13 = cleantext(item).replace('ISBN-13', '').replace(':', '').strip()

    book.url = f"https://www.amazon.com{sub_url}"

    return json.dumps(book.get_dict())


def cleantext(item):
    """Return the cleaned text from a soup item.

    Parameters
    ----------
    item : BeautifulSoup.Tag
        The item whose text is to be cleaned.

    Return
    ------
    str
        The text with whitespace removed.
    """
    return item.get_text().strip()


def check_isbn_valid(isbn):
    """Check whether the ISBN is valid and, if so, clean it.

    Parameters
    ----------
    isbn : str
        A string containing the ISBN of the book to search.

    Returns
    -------
    str
        The ISBN with hyphens removed, or an empty string if the
        ISBN is not valid.
    """
    valid_chars = []
    for char in isbn:
        if char in '0123456789X':
            valid_chars.append(char)

    if len(valid_chars) == 10 or len(valid_chars) == 13:
        return ''.join(valid_chars)
    return ''


def console():
    """Run the program, pulling the ISBN from args."""
    parser = argparse.ArgumentParser(description="Scrape Amazon.com for book details.")
    parser.add_argument('isbn',
                        help="The ISBN for the book you want to search.")
    parser.add_argument('--format', '-f', action='store',
                        help=("The binding type, usually 'paperback' or 'hardcover'."))

    args = parser.parse_args()

    isbn = check_isbn_valid(args.isbn).strip()

    fmt = args.format
    if fmt:
        fmt = fmt.strip()

    if isbn:
        if __debug__:
            log.debug("ISBN detected: %s", isbn)
        try:
            _, sub_url = get_first_result(isbn)
        except IndexError:
            log.warning('Probably bot detected. Trying again without headless.')
            _, sub_url = get_first_result(isbn, False)
        try:
            results = get_book_data(sub_url, fmt)
        except AttributeError:
            log.warning('Probably bot detected. Trying again without headless.')
            results = get_book_data(sub_url, fmt, False)
        print(results)
    else:
        sys.exit(1, 'Invalid ISBN')


def test():
    """Run the program with a test ISBN."""
    test_isbn = "9781788478120"

    _, sub_url = get_first_result(test_isbn)
    results = get_book_data(sub_url)
    print(results)


# print(h2s)
if __name__ == '__main__':
    console()
