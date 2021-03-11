# recipe-veganizer
PHP class that uses regular expressions to parse an ingredients list and then veganizes it!

## How to use

Note: the Veganizer has so far only been tested on input from an HTML form. Your mileage may vary when using it in other contexts. If you'd like to improve the portability or to contribute to the project in anyway, don't hesitate to fork. 

First, Make sure your input ingredients list is in the following format. (With new lines and/or carriage returns at the end of each line)

`Qty Unit Ingredient`

For example:
```
3 cups cake flour
1 tablespoon baking powder
3/4 teaspoon salt
2 sticks unsalted butter
1 1/2 cups granulated sugar
1 1/2 teaspoons vanilla extract
3 large eggs
1 1/4 cups whole milk 
```

Then, instantiate the class and call veganize(), passing your list as the parameter

```php
$veganizer = new RecipeVeganizer();
$veganizedRecipeText = $veganizer->veganize($recipe);
```

The output will be in HTML. Each line will end in HTML carriage return and line feed entities `&#13;&#10;`

e.g.

```
3 cups cake flour&#13;&#10;
1 tablespoon baking powder&#13;&#10;
3/4 teaspoon salt&#13;&#10;
2 sticks vegan butter&#13;&#10;
1 1/2 cups granulated sugar&#13;&#10;
1 1/2 teaspoons vanilla extract&#13;&#10;
3  flax eggs&#13;&#10;
2 1/2 cups non-dairy milk (soy, almond, oat, etc.)&#13;&#10;
```

So, printed in a DIV, it would look like this:

```
3 cups cake flour
1 tablespoon baking powder
3/4 teaspoon salt
2 sticks vegan butter
1 1/2 cups granulated sugar
1 1/2 teaspoons vanilla extract
3  flax eggs
2 1/2 cups non-dairy milk (soy, almond, oat, etc.)
```

## My to-do list

- [ ] Create Composer package
- [ ] Interfaces for difference kinds of input, most importantly, an API  
- [ ] Add more items to the substitutions dictionary and more units to the units list
- [ ] Accept decimal quantities
